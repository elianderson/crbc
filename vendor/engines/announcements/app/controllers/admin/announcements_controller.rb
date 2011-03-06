module Admin
  class AnnouncementsController < Admin::BaseController

    crudify :announcement

    def index
      search_all_announcements if searching?
      paginate_all_announcements

      render :partial => 'announcements' if request.xhr?
    end

  end
end
