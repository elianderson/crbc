module Admin
  class HumenController < Admin::BaseController

    crudify :human,
            :title_attribute => 'fname'

    def index
      search_all_humen if searching?
      paginate_all_humen

      render :partial => 'humen' if request.xhr?
    end

  end
end
