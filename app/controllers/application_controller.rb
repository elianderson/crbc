class ApplicationController < ActionController::Base
  protect_from_forgery
  
  def twFeed 
    require 'net/http'
    require 'uri'
    url = URI.parse('http://api.twitter.com/1/statuses/user_timeline.json?screen_name=ClackamasRiver')
    request = Net::HTTP::Get.new(url.path)
    response = res = Net::HTTP.start(url.host, url.port) {|http|
      http.request(request)
    }
    render :json => response.body
  end
  
end
