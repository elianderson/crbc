class HumenController < ApplicationController

  before_filter :find_all_humen
  before_filter :find_page

  def index
    # you can use meta fields from your model instead (e.g. browser_title)
    # by swapping @page for @human in the line below:
    present(@page)
  end

  def show
    @human = Human.find(params[:id])

    # you can use meta fields from your model instead (e.g. browser_title)
    # by swapping @page for @human in the line below:
    present(@page)
  end

protected

  def find_all_humen
    @humen = Human.find(:all, :order => "position ASC")
  end

  def find_page
    @page = Page.find_by_link_url("/humen")
  end

end
