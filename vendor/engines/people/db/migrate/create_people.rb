class CreatePeople < ActiveRecord::Migration

  def self.up
    create_table :people do |t|
      t.string :first_name
      t.string :last_name
      t.string :address
      t.string :city
      t.string :state
      t.integer :zip
      t.boolean :newsletter
      t.string :email
      t.integer :position

      t.timestamps
    end

    add_index :people, :id

    load(Rails.root.join('db', 'seeds', 'people.rb'))
  end

  def self.down
    UserPlugin.destroy_all({:name => "people"})

    Page.delete_all({:link_url => "/people"})

    drop_table :people
  end

end
