class CreateHumen < ActiveRecord::Migration

  def self.up
    create_table :humen do |t|
      t.string :fname
      t.string :lname
      t.string :address
      t.string :city
      t.integer :zip
      t.boolean :newsletter
      t.string :email
      t.integer :position

      t.timestamps
    end

    add_index :humen, :id

    load(Rails.root.join('db', 'seeds', 'humen.rb'))
  end

  def self.down
    UserPlugin.destroy_all({:name => "humen"})

    Page.delete_all({:link_url => "/humen"})

    drop_table :humen
  end

end
