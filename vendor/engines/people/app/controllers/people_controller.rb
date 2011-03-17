class PeopleController < ApplicationController

  before_filter :find_all_people
  before_filter :find_page

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
  	
  	respond_to do |format|
      if @person.save
       redirect_to "/"
      else
        redirect_to "/about"
      end
    end
  	
  	#redirect_to "/"
  end

protected

  def find_all_people
    @people = Person.find(:all, :order => "position ASC")
  end

  def find_page
    @page = Page.find_by_link_url("/people")
  end

end
