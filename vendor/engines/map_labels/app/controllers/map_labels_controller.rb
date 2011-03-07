class MapLabelsController < ApplicationController

  before_filter :find_all_map_labels
  before_filter :find_page

  def index
    # you can use meta fields from your model instead (e.g. browser_title)
    # by swapping @page for @map_label in the line below:
    present(@page)
  end

  def show
    @map_label = MapLabel.find(params[:id])

    # you can use meta fields from your model instead (e.g. browser_title)
    # by swapping @page for @map_label in the line below:
    present(@page)
  end

protected

  def find_all_map_labels
    @map_labels = MapLabel.find(:all, :order => "position ASC")
  end

  def find_page
    @page = Page.find_by_link_url("/map_labels")
  end

end
