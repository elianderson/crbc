require 'refinery'

module Refinery
  module People
    class Engine < Rails::Engine
      initializer "static assets" do |app|
        app.middleware.insert_after ::ActionDispatch::Static, ::ActionDispatch::Static, "#{root}/public"
      end

      config.after_initialize do
        Refinery::Plugin.register do |plugin|
          plugin.name = "people"
          plugin.activity = {
            :class => Person,
            :title => 'first_name'
          }
        end
      end
    end
  end
end
