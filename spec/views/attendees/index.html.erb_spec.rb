require 'spec_helper'

describe "attendees/index.html.erb" do
  before(:each) do
    assign(:attendees, [
      stub_model(Attendee,
        :name => "Name",
        :email => "Email",
        :event_id => 1
      ),
      stub_model(Attendee,
        :name => "Name",
        :email => "Email",
        :event_id => 1
      )
    ])
  end

  it "renders a list of attendees" do
    render
    # Run the generator again with the --webrat flag if you want to use webrat matchers
    assert_select "tr>td", :text => "Name".to_s, :count => 2
    # Run the generator again with the --webrat flag if you want to use webrat matchers
    assert_select "tr>td", :text => "Email".to_s, :count => 2
    # Run the generator again with the --webrat flag if you want to use webrat matchers
    assert_select "tr>td", :text => 1.to_s, :count => 2
  end
end
