class CreateAttendees < ActiveRecord::Migration
  def self.up
    create_table :attendees do |t|
      t.string :name
      t.string :email
      t.integer :event_id

      t.timestamps
    end
  end

  def self.down
    drop_table :attendees
  end
end
