User.find(:all).each do |user|
  if user.plugins.find_by_name('map_labels').nil?
    user.plugins.create(:name => 'map_labels',
                        :position => (user.plugins.maximum(:position) || -1) +1)
  end
end

page = Page.create(
  :title => 'Map Labels',
  :link_url => '/map_labels',
  :deletable => false,
  :position => ((Page.maximum(:position, :conditions => {:parent_id => nil}) || -1)+1),
  :menu_match => '^/map_labels(\/|\/.+?|)$'
)
Page.default_parts.each do |default_page_part|
  page.parts.create(:title => default_page_part, :body => nil)
end
