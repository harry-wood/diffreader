# Given string of an opening xml tag, parse out the attributes
# String parsing of XML is very bad practice, full of brittle assumptions...
# but it's fast.
def parse_attributes(xmltag)
  return {} unless xmltag.include?('=')
  attributes = { }
  pos = xmltag.index('<')
  space_pos = xmltag.index(' ', pos)
  element_name = xmltag[pos + 1..(space_pos - 1)]
  attributes['element_name'] = element_name
  pos = space_pos
  until xmltag.index("=\"", pos + 1).nil?
    eq_pos = xmltag.index("=\"", pos)
    key = xmltag[pos..(eq_pos - 1)].strip
    end_quote_pos = xmltag.index("\" ", eq_pos + 2)
    end_quote_pos = xmltag.index("\">", eq_pos + 2) if end_quote_pos.nil?
    end_quote_pos = xmltag.index("\"/>", eq_pos + 2) if end_quote_pos.nil?
    end_quote_pos = xmltag.index("\"?>", eq_pos + 2) if end_quote_pos.nil?
    fail "bad end quote #{eq_pos} '#{xmltag}'" if end_quote_pos.nil?
    value = xmltag[(eq_pos + 2)..(end_quote_pos - 1)].strip

    key = 'osm_id' if key == 'id'

    attributes[key] = value

    pos = end_quote_pos + 1
  end

  return attributes
end

p parse_attributes('<mytag myattr="my value" myother="this other">')
