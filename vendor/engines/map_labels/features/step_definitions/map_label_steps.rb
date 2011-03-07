Given /^I have no map_labels$/ do
  MapLabel.delete_all
end

Given /^I (only )?have map_labels titled "?([^\"]*)"?$/ do |only, titles|
  MapLabel.delete_all if only
  titles.split(', ').each do |title|
    MapLabel.create(:title => title)
  end
end

Then /^I should have ([0-9]+) map_labels?$/ do |count|
  MapLabel.count.should == count.to_i
end
