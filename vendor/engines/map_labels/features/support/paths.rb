module NavigationHelpers
  module Refinery
    module MapLabels
      def path_to(page_name)
        case page_name
        when /the list of map_labels/
          admin_map_labels_path

         when /the new map_label form/
          new_admin_map_label_path
        else
          nil
        end
      end
    end
  end
end
