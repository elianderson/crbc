require 'refinery'

module Refinery
  module Humen
    class Engine < Rails::Engine
      initializer "static assets" do |app|
        app.middleware.insert_after ::ActionDispatch::Static, ::ActionDispatch::Static, "#{root}/public"
      end

      config.after_initialize do
        Refinery::Plugin.register do |plugin|
          plugin.name = "humen"
          plugin.activity = {
            :class => Human,
            :title => 'fname'
          }
        end
      end
    end
  end
end
