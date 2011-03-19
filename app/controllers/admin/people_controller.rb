module Admin
  class PeopleController < Admin::BaseController

    crudify :person,
            :title_attribute => 'first_name'

	#before_filter :find_all_events

    def index
      search_all_people if searching?
      paginate_all_people
	  @events = Event.find(:all, :order => "position ASC")
	  @test = "this is work"
      render :partial => 'people' if request.xhr?
    end
  end
  
  def update
    @person = Person.find(params[:id])
	#@events = Event.find(:all, :order => "position ASC")
	@test = "this is work"
    # you can use meta fields from your model instead (e.g. browser_title)
    # by swapping @page for @person in the line below:
    present(@page)
  end
  
  protected
  
  def find_all_events
    @events = Event.find(:all, :order => "position ASC")
  end
  
end
