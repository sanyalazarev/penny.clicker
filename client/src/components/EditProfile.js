import React, { useState, useEffect, useContext } from 'react';
import { useNavigate, Link } from 'react-router-dom';

import { apiUrl } from '../config';

import { useLanguage } from '../LanguageContext';
import { ModalContext } from '../ModalContext';

import Header from './Header';

const EditProfile = () => {
  const { translate } = useLanguage();

  const { showError, showInfo } = useContext(ModalContext);

  const navigate = useNavigate();

  const [profile, setProfile] = useState({
    photo: '',
    nickname: '',
    about: '',
    receive_notifications: 0
  });

  const handleChange = (e) => {
    const { name, type, checked, value, files } = e.target;

    if (type === 'file') {
      setProfile({ ...profile, [name]: files[0]});
    } else if(type === 'checkbox') {
      setProfile({ ...profile, [name]: (checked ? 1 : 0) });
    } else {
      setProfile({ ...profile, [name]: value });
    }
  };

  const fetchUserInfo = async () => {
    try {
      const response = await fetch(apiUrl + '/user/getInfo ', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ token: window.token })
      });

      if (response.ok) {
        const data = await response.json();

        if(data.success) {
          setProfile(data.user);
        } else {
          showError(data.error);
        }
      } else {
        console.error(response.statusText);
      }
    } catch (error) {
      console.error('Error loading data:', error);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      const formData = new FormData();
      formData.append('token', window.token);

      for (const key in profile) {
        formData.append(key, profile[key]);
      }

      const response = await fetch(apiUrl + '/user/editInfo', {
        method: 'POST',
        body: formData
      });

      if (response.ok) {
        const data = await response.json();

        if(data.success) {
          showInfo(
            translate.profile_update, 
            translate.data_updated_successfully, 
            () => { navigate('/profile'); }
          );
        } else {
          showError(data.error);
        }
      } else {
        console.error(response.statusText);
      }
    } catch (error) {
      console.error('Error loading data:', error);
    }
  };

  useEffect(() => {
    fetchUserInfo();
  }, []);

  return (
    <>
      <Header />
      <div className="container mt-4 px-4">
        <h2 className="text-center mb-3">{translate.edit_profile}</h2>
        <form onSubmit={handleSubmit}>
          <fieldset>
            <div className="row">
              <label htmlFor="photo" className="form-label mt-3">{translate.photo}</label>
              <input type="file" name="photo" className="form-control rounded-pill" id="photo" onChange={handleChange} />
            </div>
            <div className="row">
              <label htmlFor="nickname" className="form-label mt-3">{translate.nickname}</label>
              <input type="text" name="nickname" value={profile.nickname} className="form-control rounded-pill" id="username" placeholder={translate.nickname_placeholder} onChange={handleChange} />
            </div>
            <div className="row">
              <label htmlFor="about" className="form-label mt-3">{translate.about_me2}</label>
              <textarea name="about" value={profile.about} className="form-control" id="about" rows="3" placeholder={translate.about_text} onChange={handleChange}></textarea>
            </div>
            <div className="row">
              <div className="form-check form-switch mt-3">
                <input className="form-check-input" type="checkbox" name="receive_notifications" value="1" checked={profile.receive_notifications === 1} id="switcher" onChange={handleChange} />
                <label htmlFor="switcher" className="form-check-label">{translate.send_notifications}</label>
              </div>
            </div>
            <div className="row">
              <button type="submit" className="btn btn-primary rounded-pill my-3 w-100">&#10003; {translate.save}</button>
              <Link to="/profile" className="btn btn-light rounded-pill w-100">&larr; {translate.back_to_profile}</Link>
            </div>
          </fieldset>
        </form>
      </div>
    </>
  );
};

export default EditProfile;