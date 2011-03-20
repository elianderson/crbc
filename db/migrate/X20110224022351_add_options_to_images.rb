class AddOptionsToImages < ActiveRecord::Migration
  def self.up
    add_column :images, :gallery, :boolean, :default=>0
    add_column :images, :home, :boolean, :default=>0
  end

  def self.down
    remove_column :images, :home
    remove_column :images, :gallery
  end
end
