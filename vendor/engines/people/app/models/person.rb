class Person < ActiveRecord::Base

  acts_as_indexed :fields => [:first_name, :last_name, :address, :city, :state, :email]
  
  validates :email, :presence => true, :uniqueness => true
  
end
