require 'spec_helper'

describe MapLabel do

  def reset_map_label(options = {})
    @valid_attributes = {
      :id => 1,
      :title => "RSpec is great for testing too"
    }

    @map_label.destroy! if @map_label
    @map_label = MapLabel.create!(@valid_attributes.update(options))
  end

  before(:each) do
    reset_map_label
  end

  context "validations" do
    
    it "rejects empty title" do
      MapLabel.new(@valid_attributes.merge(:title => "")).should_not be_valid
    end

    it "rejects non unique title" do
      # as one gets created before each spec by reset_map_label
      MapLabel.new(@valid_attributes).should_not be_valid
    end
    
  end

end