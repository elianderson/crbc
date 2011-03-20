class Person < ActiveRecord::Base

  has_and_belongs_to_many :events

  acts_as_indexed :fields => [:first_name, :last_name, :address, :city, :state, :email]
  
  validates :email, :presence => true, :uniqueness => true
  
end
