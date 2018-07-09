require 'rubygems'
require 'sqlite3'
require 'httpclient'
require 'uri'

require_relative 'crapxml.rb'

# create db file if it doesnt exist, and create coords table if it doesn't exist
def init_db
  db_filename = './coord_cache.db'
  puts "Creating db file '#{db_filename}'" unless File.exist?(db_filename)
  @coord_db = SQLite3::Database.new(db_filename)
  @coord_db.execute('CREATE TABLE IF NOT EXISTS coords ' \
              '(id INTEGER PRIMARY KEY AUTOINCREMENT, ' \
              ' osm_id BIGINT, ' \
              ' lat REAL, ' \
              ' lon REAL)')
end

def get_from_db(id)
  init_db unless @coord_db
  res = @coord_db.execute('SELECT lat, lon FROM coords WHERE osm_id=?;', [id])
  if res.empty?
    return nil, nil
  else
    return res[0][0], res[0][1]
  end
end

def save_to_db(osm_id, lat, lon)
  init_db unless @coord_db
  @coord_db.execute('INSERT INTO coords (osm_id, lat, lon) VALUES (?, ?, ?);',
              [osm_id, lat, lon])
end

def request(url)
  uri = URI.parse(url)
  http = Net::HTTP.new(uri.host, uri.port)
  request = Net::HTTP::Get.new(uri.request_uri)
  response = http.request(request)
  return response
end

def request(url)
  @client ||= HTTPClient.new
  @client.get(url) #, query)
end

def fetch_from_osm(id)
  sleep(1.0) # be nice to the OSM API server
  
  url = "https://www.openstreetmap.org/api/0.6/node/#{id}?contact=harry_wood"
  puts "Fetching #{url}"
  response = request(url)
  if response.status == 410
  	puts 'Node deleted. Finding location from history call instead'
  	return fetch_deleted_node_location_in_history(id)
  elsif response.status != 200
    fail "Error '#{response.body}' (#{response.status}) getting url: #{url}"
  end
  node_body = response.body 

  # find the right line of xml
  lines = node_body.split("\n", 4)
  node_line = nil
  line_no = -1
  until node_line
    line_no += 1
    line = lines[line_no]
    node_line = line if line.include?('<node')
  end
  
  attributes = parse_attributes(node_line)
  
  [attributes['lat'].to_f, attributes['lon'].to_f]
end

def fetch_deleted_node_location_in_history(id)
  url = "https://www.openstreetmap.org/api/0.6/node/#{id}/history?contact=harry_wood"
  puts "Fetching #{url}"
  response = request(url)
  if response.status != 200
    fail "Error '#{response.body}' (#{response.status}) getting url: #{url}"
  end
  node_history_body = response.body 

  lat = nil
  lon = nil
  
  lines = node_history_body.split("\n")
  lines.each do |line|
    	 
    next unless line.include?('<node')
     
    attributes = parse_attributes(line)
    unless attributes['lat'].nil? && attributes['lon'].nil?
      # Set lat and lon as the last pair encountered in the file
      # (which will *not* be the last line/version where the nodes is deleted)
      lat = attributes['lat']
      lon = attributes['lon']
    end
   
  end # next line
	
  [lat.to_f, lon.to_f]
end

def node_location(id)
  lat, lon = get_from_db(id)
  return lat, lon if lat && lon # return from db if it's there

  lat, lon = fetch_from_osm(id) # otherways request from OSM
  
  save_to_db(id, lat, lon)

  [lat, lon]
end

# ---------
# File is to be 'required' really. This executable bit is just to do a test.

p parse_attributes('<mytag myattr="my value" myother="this other">')
p node_location(2766314567)
p node_location(395230836) #deleted node
