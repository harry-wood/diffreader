# If this script is causing load problems on the OSM server then the sysadmins
# would like to know who's running it, so we put the email address of this
# person in the URLs when we're doing all changefile download requests.
# This is polite, but it's unlikely to get used.
@contact = 'mail@harrywood.co.uk'

# Sleep for this number of seconds between calls when making multiple calls
# in sucesssion. Note this applies to fetching planet diff files, which
# isn't terribly expensive, and typically only happens rapidly during a period
# of "catch up" (when you might want it to happen rapidly)
@call_delay = 0.1
