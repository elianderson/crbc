require 'spec_helper'

describe AttendeesController do

  def mock_attendee(stubs={})
    (@mock_attendee ||= mock_model(Attendee).as_null_object).tap do |attendee|
      attendee.stub(stubs) unless stubs.empty?
    end
  end

  describe "GET index" do
    it "assigns all attendees as @attendees" do
      Attendee.stub(:all) { [mock_attendee] }
      get :index
      assigns(:attendees).should eq([mock_attendee])
    end
  end

  describe "GET show" do
    it "assigns the requested attendee as @attendee" do
      Attendee.stub(:find).with("37") { mock_attendee }
      get :show, :id => "37"
      assigns(:attendee).should be(mock_attendee)
    end
  end

  describe "GET new" do
    it "assigns a new attendee as @attendee" do
      Attendee.stub(:new) { mock_attendee }
      get :new
      assigns(:attendee).should be(mock_attendee)
    end
  end

  describe "GET edit" do
    it "assigns the requested attendee as @attendee" do
      Attendee.stub(:find).with("37") { mock_attendee }
      get :edit, :id => "37"
      assigns(:attendee).should be(mock_attendee)
    end
  end

  describe "POST create" do

    describe "with valid params" do
      it "assigns a newly created attendee as @attendee" do
        Attendee.stub(:new).with({'these' => 'params'}) { mock_attendee(:save => true) }
        post :create, :attendee => {'these' => 'params'}
        assigns(:attendee).should be(mock_attendee)
      end

      it "redirects to the created attendee" do
        Attendee.stub(:new) { mock_attendee(:save => true) }
        post :create, :attendee => {}
        response.should redirect_to(attendee_url(mock_attendee))
      end
    end

    describe "with invalid params" do
      it "assigns a newly created but unsaved attendee as @attendee" do
        Attendee.stub(:new).with({'these' => 'params'}) { mock_attendee(:save => false) }
        post :create, :attendee => {'these' => 'params'}
        assigns(:attendee).should be(mock_attendee)
      end

      it "re-renders the 'new' template" do
        Attendee.stub(:new) { mock_attendee(:save => false) }
        post :create, :attendee => {}
        response.should render_template("new")
      end
    end

  end

  describe "PUT update" do

    describe "with valid params" do
      it "updates the requested attendee" do
        Attendee.should_receive(:find).with("37") { mock_attendee }
        mock_attendee.should_receive(:update_attributes).with({'these' => 'params'})
        put :update, :id => "37", :attendee => {'these' => 'params'}
      end

      it "assigns the requested attendee as @attendee" do
        Attendee.stub(:find) { mock_attendee(:update_attributes => true) }
        put :update, :id => "1"
        assigns(:attendee).should be(mock_attendee)
      end

      it "redirects to the attendee" do
        Attendee.stub(:find) { mock_attendee(:update_attributes => true) }
        put :update, :id => "1"
        response.should redirect_to(attendee_url(mock_attendee))
      end
    end

    describe "with invalid params" do
      it "assigns the attendee as @attendee" do
        Attendee.stub(:find) { mock_attendee(:update_attributes => false) }
        put :update, :id => "1"
        assigns(:attendee).should be(mock_attendee)
      end

      it "re-renders the 'edit' template" do
        Attendee.stub(:find) { mock_attendee(:update_attributes => false) }
        put :update, :id => "1"
        response.should render_template("edit")
      end
    end

  end

  describe "DELETE destroy" do
    it "destroys the requested attendee" do
      Attendee.should_receive(:find).with("37") { mock_attendee }
      mock_attendee.should_receive(:destroy)
      delete :destroy, :id => "37"
    end

    it "redirects to the attendees list" do
      Attendee.stub(:find) { mock_attendee }
      delete :destroy, :id => "1"
      response.should redirect_to(attendees_url)
    end
  end

end
