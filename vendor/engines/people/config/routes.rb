Refinery::Application.routes.draw do
  resources :people

  scope(:path => 'refinery', :as => 'admin', :module => 'admin') do
    resources :people, :except => :show do
      collection do
        post :update_positions
      end
    end
  end
end
