Given /^I have no humen$/ do
  Human.delete_all
end

Given /^I (only )?have humen titled "?([^\"]*)"?$/ do |only, titles|
  Human.delete_all if only
  titles.split(', ').each do |title|
    Human.create(:fname => title)
  end
end

Then /^I should have ([0-9]+) humen?$/ do |count|
  Human.count.should == count.to_i
end
