module NavigationHelpers
  module Refinery
    module Humen
      def path_to(page_name)
        case page_name
        when /the list of humen/
          admin_humen_path

         when /the new human form/
          new_admin_human_path
        else
          nil
        end
      end
    end
  end
end
