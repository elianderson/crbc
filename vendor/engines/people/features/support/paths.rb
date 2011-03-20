module NavigationHelpers
  module Refinery
    module People
      def path_to(page_name)
        case page_name
        when /the list of people/
          admin_people_path

         when /the new person form/
          new_admin_person_path
        else
          nil
        end
      end
    end
  end
end
