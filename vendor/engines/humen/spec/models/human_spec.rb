require 'spec_helper'

describe Human do

  def reset_human(options = {})
    @valid_attributes = {
      :id => 1,
      :title => "RSpec is great for testing too"
    }

    @human.destroy! if @human
    @human = Human.create!(@valid_attributes.update(options))
  end

  before(:each) do
    reset_human
  end

  context "validations" do
    
    it "rejects empty fname" do
      Human.new(@valid_attributes.merge(:fname => "")).should_not be_valid
    end

    it "rejects non unique fname" do
      # as one gets created before each spec by reset_human
      Human.new(@valid_attributes).should_not be_valid
    end
    
  end

end