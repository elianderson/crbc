class AddInfoToImages < ActiveRecord::Migration
  def self.up
    add_column :images, :home_title, :string, :default => 'Title'
    add_column :images, :home_link, :string, :default => '#link'
    add_column :images, :home_link_text, :string, :default => 'Link Text'
    add_column :images, :home_description, :string, :default => 'Image Description'
  end

  def self.down
    remove_column :images, :home_description
    remove_column :images, :home_link_text
    remove_column :images, :home_link
    remove_column :images, :home_title
  end
end
