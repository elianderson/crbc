class EventsPeople < ActiveRecord::Migration
  def self.up
	create_table :events_people, :id => false do |t|
	  t.references :event, :person
	end
  end

  def self.down
    drop_table :events_people
  end

end
