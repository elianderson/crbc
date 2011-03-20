class Event < ActiveRecord::Base
	
  has_many :attendees

  acts_as_indexed :fields => [:title, :location_name, :location_street_address, :location_state, :location_zip, :description]
  
  validates :title, :presence => true, :uniqueness => true
  
end
