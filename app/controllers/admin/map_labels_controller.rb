module Admin
  class MapLabelsController < Admin::BaseController

    crudify :map_label

    def index
      search_all_map_labels if searching?
      paginate_all_map_labels

      render :partial => 'map_labels' if request.xhr?
    end

  end
end
