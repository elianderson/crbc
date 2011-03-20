require "spec_helper"

describe AttendeesController do
  describe "routing" do

    it "recognizes and generates #index" do
      { :get => "/attendees" }.should route_to(:controller => "attendees", :action => "index")
    end

    it "recognizes and generates #new" do
      { :get => "/attendees/new" }.should route_to(:controller => "attendees", :action => "new")
    end

    it "recognizes and generates #show" do
      { :get => "/attendees/1" }.should route_to(:controller => "attendees", :action => "show", :id => "1")
    end

    it "recognizes and generates #edit" do
      { :get => "/attendees/1/edit" }.should route_to(:controller => "attendees", :action => "edit", :id => "1")
    end

    it "recognizes and generates #create" do
      { :post => "/attendees" }.should route_to(:controller => "attendees", :action => "create")
    end

    it "recognizes and generates #update" do
      { :put => "/attendees/1" }.should route_to(:controller => "attendees", :action => "update", :id => "1")
    end

    it "recognizes and generates #destroy" do
      { :delete => "/attendees/1" }.should route_to(:controller => "attendees", :action => "destroy", :id => "1")
    end

  end
end
