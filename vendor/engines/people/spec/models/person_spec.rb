require 'spec_helper'

describe Person do

  def reset_person(options = {})
    @valid_attributes = {
      :id => 1,
      :title => "RSpec is great for testing too"
    }

    @person.destroy! if @person
    @person = Person.create!(@valid_attributes.update(options))
  end

  before(:each) do
    reset_person
  end

  context "validations" do
    
    it "rejects empty first_name" do
      Person.new(@valid_attributes.merge(:first_name => "")).should_not be_valid
    end

    it "rejects non unique first_name" do
      # as one gets created before each spec by reset_person
      Person.new(@valid_attributes).should_not be_valid
    end
    
  end

end