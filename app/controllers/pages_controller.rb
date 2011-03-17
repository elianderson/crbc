class PagesController < ApplicationController
	
  # This action is usually accessed with the root path, normally '/'
  def home
    error_404 unless (@page = Page.where(:link_url => '/').first).present?
    @gallery = Image.where(:home => 1).order('id DESC').limit(5)
    @person = Person.new
    @announcements = Announcement.all
    
  end

  # This action can be accessed normally, or as nested pages.
  # Assuming a page named "mission" that is a child of "about",
  # you can access the pages with the following URLs:
  #
  #   GET /pages/about
  #   GET /about
  #
  #   GET /pages/mission
  #   GET /about/mission
  #
  def show
    # Find the page by the newer 'path' or fallback to the page's id if no path.
    @page = Page.find(params[:path] ? params[:path].to_s.split('/').last : params[:id])
    @person = Person.new
    @announcements = Announcement.all

    if @page.try(:live?) or (refinery_user? and current_user.authorized_plugins.include?("refinery_pages"))
      # if the admin wants this to be a "placeholder" page which goes to its first child, go to that instead.
      if @page.skip_to_first_child and (first_live_child = @page.children.order('lft ASC').where(:draft=>false).first).present?
        redirect_to first_live_child.url
      end
    else
      error_404
    end
  end
  
  def show_gallery
   error_404 unless (@page = Page.where(:title => 'Photo Gallery').first).present?
   @gallery = Image.where(:gallery => 1).order('id DESC')
  end
         
  def show_map
  	error_404 unless (@page = Page.where(:title => 'Where We Work').first).present?
  	@labels = MapLabel.all
  end

end
