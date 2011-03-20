require 'spec_helper'

describe "attendees/edit.html.erb" do
  before(:each) do
    @attendee = assign(:attendee, stub_model(Attendee,
      :name => "MyString",
      :email => "MyString",
      :event_id => 1
    ))
  end

  it "renders the edit attendee form" do
    render

    # Run the generator again with the --webrat flag if you want to use webrat matchers
    assert_select "form", :action => attendee_path(@attendee), :method => "post" do
      assert_select "input#attendee_name", :name => "attendee[name]"
      assert_select "input#attendee_email", :name => "attendee[email]"
      assert_select "input#attendee_event_id", :name => "attendee[event_id]"
    end
  end
end
