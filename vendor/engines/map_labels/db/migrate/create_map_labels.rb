class CreateMapLabels < ActiveRecord::Migration

  def self.up
    create_table :map_labels do |t|
      t.string :title
      t.text :description
      t.integer :position

      t.timestamps
    end

    add_index :map_labels, :id

    load(Rails.root.join('db', 'seeds', 'map_labels.rb'))
  end

  def self.down
    UserPlugin.destroy_all({:name => "map_labels"})

    Page.delete_all({:link_url => "/map_labels"})

    drop_table :map_labels
  end

end
