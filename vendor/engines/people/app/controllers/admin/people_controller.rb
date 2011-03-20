module Admin
  class PeopleController < Admin::BaseController

    crudify :person,
            :title_attribute => 'first_name'

    def index
      search_all_people if searching?
      paginate_all_people

      render :partial => 'people' if request.xhr?
    end

  end
end
