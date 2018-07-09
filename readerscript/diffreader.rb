require 'open-uri'
require 'zlib'
require 'rubygems'
require 'sqlite3'

require_relative 'settings.rb'
require_relative 'crapxml.rb'
require_relative 'node_location.rb'

# This script consumes the minutely diffs and processes them in some way
#
# It was created by Harry Wood initially to power the wimbledon tennis eckdit
# tracker displays. The same basic parsing principle could be used for all
# kinds of things but this is simply finding mention of a particular tag, and
# recording details of these matching edits to an SQLite DB & CSV
#
# It includes a loop with a 60 second wait, so like this it's designed to run
# forever. The other way would be to set up a minutely cronjob to run this
# script and have it only do a single iteration each time.
#
# Within each minute iteration it has an inner loop which will run once or
# however many times is needed to "catch up" to the latest minutely diff. This
# is based on the contents of 'on_seq.txt' which should contain the sequence
# number of the changefile we are on (processed already). You will need to
# create this file and set it with the "current" sequence number.
#
# This mechanism is good for allowing you to restart the processing later if
# the process dies.  TODO: make it request hourly diffs instead of minutely if
# appropriate in these circumstances.

# figure out the AAA/BBB/CCC filename from the given integer sequence number
def seq_to_filename(seq)
  seqs = seq.to_s
  while seqs.length < 9
    seqs = '0' + seqs
  end
  return "#{seqs[0..2]}/#{seqs[3..5]}/#{seqs[6..8]}"
end

# Process a change file. Unzipping it and parsing the XML.
def process_change_file(url)
  puts "Fetching #{url}"

  matches = 0
  begin
    diff_file = Zlib::GzipReader.new(open(url))

    node_coords = {}
    mode = ''
    line_type = nil
    changeset_id = nil
    attributes = nil
    ref_id = nil
    lat = nil
    lon = nil

    diff_file.each_line do |line|
      # set the state of the mode and line_type vars but we don't do
      # anything until we hit a <tag> on subsequent lines

      mode = 'create' if line.include?('<create>')
      mode = 'modify' if line.include?('<modify>')
      mode = 'delete' if line.include?('<delete>')

      if line.include?('<node')
        line_type = 'node'

        # keep a record of all node coords we encounter
        attributes = parse_attributes(line)
        osm_id = attributes['osm_id']
        changeset_id = attributes['changeset']
        lat = attributes['lat']
        lon = attributes['lon']
        node_coords[osm_id] = [lat, lon]

      elsif line.include?('<way')
        line_type = 'way'
        attributes = parse_attributes(line)
        changeset_id = attributes['changeset']
        lat = nil
        lon = nil

      elsif line.include?('<relation')
        line_type = 'relation'
        attributes = parse_attributes(line)
        changeset_id = attributes['changeset']
        lat = nil
        lon = nil

      elsif line.include?('<nd') && lat.nil? && lon.nil?
        nd_attributes = parse_attributes(line)
        ref_id = nd_attributes['ref']
        lat, lon = node_coords[ref_id] # see if we've encountered this node
      end

      if line.include?('<tag') &&
         !line_type.nil? && (line_type == 'node' || line_type == 'way')
        # Filter by tag
        if line.include?("<tag k=\"#{@filter_key}\" v=\"#{@filter_value}\"/>")
          # We have a match. Spit out CSV and insert DB record
          matches += 1

          if lat.nil?
            if line_type == 'way'
              # We didn't encounter the node which is referenced in this
              # way. This will happen when somebody edits a way without
              # editing any node (e.g. just editing tags)
              # So we have no data in the changeset file about where this
              # way is. Do a request to look the node up in this case
              lat, lon = node_location(ref_id)
            else
              fail "missing lat on node. line_type=#{line_type}" # should never happen
            end
          end

          osm_id = attributes['osm_id']
          osm_id = '-' if osm_id.nil?
          csv = "#{attributes['timestamp']}, #{mode}, " \
                "#{attributes['element_name']}:#{osm_id}, " \
                "\"#{attributes['user']}\", #{changeset_id}, #{lat}, #{lon}"
          @csv_file.write csv + "\n"
          @csv_file.flush

          @edits_db.execute('INSERT INTO edits ' \
             '(timestamp, op_type, element_type, osm_id, ' \
             'user_name, changeset, lat, lon) ' \
             'VALUES (?, ?, ?, ?, ?, ?, ?, ?);',
                      [attributes['timestamp'],
                       mode,
                       attributes['element_name'],
                       osm_id,
                       attributes['user'],
                       attributes['changeset'],
                       lat,
                       lon]) unless @edits_db.nil?
          lat = nil
          lon = nil
        end
      end
    end
  rescue => e
    p e
    puts e.backtrace
    puts "Getting #{url}"
    throw e
  end

  puts "#{matches} matched edits"
end

def read_or_init_seq_file
  # Find out what the last processed sequence number was from local file
  seq_file_path = "../#{@directory}/on_seq.txt"
  if File.exist?(seq_file_path)
    seq_file = File.new(seq_file_path, 'r')
    on_seq = seq_file.gets.to_i
    seq_file.close
  else
    on_seq = read_state_txt_file[0]
    puts "Sequence file #{seq_file_path} doesn't exist. " \
         "Initialising with the latest changeset state.txt value #{on_seq}"
    File.open(seq_file_path, 'w') do |f|
      f.write "#{on_seq}"
    end
  end
  on_seq
end

def update_seq_file(on_seq)
  seq_file_path = "../#{@directory}/on_seq.txt"
  on_seq_file = File.new(seq_file_path, 'w')
  on_seq_file.write(on_seq.to_s)
  on_seq_file.close
end


def read_state_txt_file
  # Get details of what the latest available changefile is from state.txt
  state_file_url =
    'http://planet.openstreetmap.org/replication/minute/state.txt?' \
    "contact=#{@contact}"
  state_text = open(state_file_url) { |io| io.read } # read to string

  available_seq = 0
  available_timestamp = nil
  state_text.each_line do |line|
    available_timestamp = line[10..99] if line[0..9] == 'timestamp='
    available_seq = line[15..99].to_i if line[0..14] == 'sequenceNumber='
  end

  [available_seq, available_timestamp]
end

# -------------

fail '@contact setting not set' unless @contact

ARGV.each do|a|
  k, v = a.split('=', 2)
  @directory = v if k == 'directory'
end

fail 'directory= command line arg required' unless @directory
require_relative "../#{@directory}/diffreader-settings.rb"

@csv_file = File.open("../#{@directory}/edits.csv", 'a+')

output_db = "../#{@directory}/edits.db"

puts "Creating db file '#{output_db}'" unless File.exist?(output_db)
@edits_db = SQLite3::Database.new(output_db)
@edits_db.execute('CREATE TABLE IF NOT EXISTS edits ' \
            '(id INTEGER PRIMARY KEY AUTOINCREMENT, ' \
            ' timestamp VARCHAR(40), ' \
            ' op_type VARCHAR(10), ' \
            ' element_type VARCHAR(10), ' \
            ' osm_id BIGINT, ' \
            ' user_name VARCHAR(255), ' \
            ' changeset INT, ' \
            ' lat REAL, ' \
            ' lon REAL);')

on_seq = read_or_init_seq_file

puts "on_seq from file: #{on_seq}"

# Loop forever (or until the process dies for some reason)
while true
  available_seq, available_timestamp = read_state_txt_file
  puts "available_seq: #{available_seq}"
  puts "available_timestamp: #{available_timestamp}"

  # Loop until we're up to date.
  # During normal operation the loop would run only once to process the latest
  # minutes changefile
  while on_seq < available_seq
    sleep @call_delay 
    on_seq += 1
    puts "on_seq = #{on_seq}"
    change_file_url = 'http://planet.openstreetmap.org/replication/minute/' \
                      "#{seq_to_filename(on_seq)}.osc.gz?contact=harry_wood"
    process_change_file(change_file_url)
    $stdout.flush

    update_seq_file(on_seq)
  end

  sleep 60

  # After waiting a minute we'd expect a new changefile to be available ...loop
end

# never reaches here, but if it did...
@csv_file.close
