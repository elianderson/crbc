class Event < ActiveRecord::Base
	
  has_and_belongs_to_many :people

  acts_as_indexed :fields => [:title, :location_name, :location_street_address, :location_state, :location_zip, :description]
  
  validates :title, :presence => true, :uniqueness => true
  
end
