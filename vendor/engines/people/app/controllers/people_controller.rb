class PeopleController < ApplicationController

  before_filter :find_all_people
  before_filter :find_page
  before_filter :find_all_events

  def index
    # you can use meta fields from your model instead (e.g. browser_title)
    # by swapping @page for @person in the line below:
    present(@page)

  end

  def show
    @person = Person.find(params[:id])

    # you can use meta fields from your model instead (e.g. browser_title)
    # by swapping @page for @person in the line below:
    present(@page)
  end

  def create
  	@person = Person.new(params[:person])
  	@person.save
  	redirect_to "/thank-you-for-signing-up-for-our-newsletter"
  	
  end

protected

  def find_all_people
    @people = Person.find(:all, :order => "position ASC")
  end
  
  def find_all_events
    @events = Event.find(:all, :order => "position ASC")
  end

  def find_page
    @page = Page.find_by_link_url("/people")
  end

end
