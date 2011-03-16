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

  def new_person
  	Person.new
  	
    format.html # new.html.erb
      format.xml  { render :xml => @product }
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
