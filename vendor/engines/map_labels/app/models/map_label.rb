class MapLabel < ActiveRecord::Base

  acts_as_indexed :fields => [:title, :description]
  
  validates :title, :presence => true, :uniqueness => true
  
end
