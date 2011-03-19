module Admin
  class EventsController < Admin::BaseController

	before_filter :find_all_people 
	before_filter :find_attendees


    crudify :event

    def index
      search_all_events if searching?
      paginate_all_events

      render :partial => 'events' if request.xhr?
    end

	private
	
	def find_all_people
		@people = Person.all
	end

	def find_attendees
		current_event = Event.find_by_id(params[:id])
		if current_event
			event_person_ids = current_event.person_ids
			@att = Person.find_all_by_id(event_person_ids)
		end
	end

  end
end
