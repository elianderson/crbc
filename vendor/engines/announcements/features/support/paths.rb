module NavigationHelpers
  module Refinery
    module Announcements
      def path_to(page_name)
        case page_name
        when /the list of announcements/
          admin_announcements_path

         when /the new announcement form/
          new_admin_announcement_path
        else
          nil
        end
      end
    end
  end
end
