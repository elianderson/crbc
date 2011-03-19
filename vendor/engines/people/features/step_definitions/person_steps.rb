Given /^I have no people$/ do
  Person.delete_all
end

Given /^I (only )?have people titled "?([^\"]*)"?$/ do |only, titles|
  Person.delete_all if only
  titles.split(', ').each do |title|
    Person.create(:first_name => title)
  end
end

Then /^I should have ([0-9]+) people?$/ do |count|
  Person.count.should == count.to_i
end
