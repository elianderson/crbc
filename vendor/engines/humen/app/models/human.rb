class Human < ActiveRecord::Base

  acts_as_indexed :fields => [:fname, :lname, :address, :city, :email]
  
  validates :fname, :presence => true, :uniqueness => true
  
end
