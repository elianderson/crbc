module Admin
  class PeopleController < Admin::BaseController

	before_filter :find_all_events

    crudify :person,
            :title_attribute => 'first_name'

    def index
      search_all_people if searching?
      paginate_all_people
      
      render :partial => 'people' if request.xhr?
    end
  end
  
  def edit
    @person = Person.find(params[:id])
	@test = "this is work"

    # you can use meta fields from your model instead (e.g. browser_title)
    # by swapping @page for @person in the line below:
    present(@page)
  end
  
  private
  
  def find_all_events
    @events = Event.all
  end
  
end
