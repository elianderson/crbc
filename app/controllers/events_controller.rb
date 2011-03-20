class EventsController < ApplicationController

  before_filter :find_all_events
  before_filter :find_page
  before_filter :new_person
  before_filter :load_attendees

  def index
    @announcements_widget = Announcement.limit(5)
    @events_widget = Event.limit(5)
    
    # you can use meta fields from your model instead (e.g. browser_title)
    # by swapping @page for @event in the line below:
    present(@page)
  end

  def show
    @event = Event.find(params[:id])
    @announcements_widget = Announcement.limit(5)
    @events_widget = Event.limit(5)
    
    # you can use meta fields from your model instead (e.g. browser_title)
    # by swapping @page for @event in the line below:
    present(@page)
  end

protected

  def find_all_events
    @events = Event.find(:all, :order => "position ASC")
  end

  def find_page
    @page = Page.find_by_link_url("/events")
  end

  def new_person
  	@person = Person.new
  end
  
  def load_attendees
    @attendees = Attendee.all
  	@attendee = Attendee.new
  end

end
