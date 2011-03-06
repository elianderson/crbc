Given /^I have no announcements$/ do
  Announcement.delete_all
end

Given /^I (only )?have announcements titled "?([^\"]*)"?$/ do |only, titles|
  Announcement.delete_all if only
  titles.split(', ').each do |title|
    Announcement.create(:title => title)
  end
end

Then /^I should have ([0-9]+) announcements?$/ do |count|
  Announcement.count.should == count.to_i
end
