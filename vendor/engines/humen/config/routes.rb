Refinery::Application.routes.draw do
  resources :humen, :only => [:index, :show]

  scope(:path => 'refinery', :as => 'admin', :module => 'admin') do
    resources :humen, :except => :show do
      collection do
        post :update_positions
      end
    end
  end
end
