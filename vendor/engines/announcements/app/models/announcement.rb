class Announcement < ActiveRecord::Base

  acts_as_indexed :fields => [:title, :blurb]
  
  validates :title, :presence => true, :uniqueness => true
  
end
