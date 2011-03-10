Refinery::Application.routes.draw do
  resources :map_labels, :only => [:index, :show]

  scope(:path => 'refinery', :as => 'admin', :module => 'admin') do
    resources :map_labels, :except => :show do
      collection do
        post :update_positions
      end
    end
  end
end
