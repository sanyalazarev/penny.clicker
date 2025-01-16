import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';

import { apiUrl } from '../config';

import { useLanguage } from '../LanguageContext';
import { getPhoto } from '../utils';

import Header from './Header';

const Profile = () => {
  const { translate } = useLanguage();

  const [profileInfo, setProfileInfo] = useState({user: {photo: '', nickname: 'Unknown', about: ''}, tasks: {my: 0, active: 0, completed: 0, declined: 0}});

  const fetchProfileInfo = async () => {
    try {
      const response = await fetch(apiUrl + '/user/getProfileInfo ', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ token: window.token })
      });

      if (response.ok) {
        const data = await response.json();

        if(data.success) {
          setProfileInfo({user: data.user, tasks: data.tasks});
        } else {
          alert(data.error);
        }
      } else {
        console.error(response.statusText);
      }
    } catch (error) {
      console.error('Error loading data:', error);
    }
  };

  useEffect(() => {
    fetchProfileInfo();
  }, []);

  return (
    <>
      <Header />

      <div className="container mt-4">
        <div className="row">
          <div className="col-8 offset-2">
            <img src={getPhoto(profileInfo.user.photo)} alt="Profile" className="img-thumbnail mb-3" />
          </div>
        </div>

        <div className="row">
          <div className="col-12 text-center">
            <h3 className="mb-0">@{profileInfo.user.nickname || 'Unknown'}</h3>
            <p>{profileInfo.user.about || translate.about_me}</p>
          </div>
        </div>

        <div className="row">
          <div className="col-12">
            <ul className="list-group mb-3">
              <li key="p1" className="list-group-item d-flex justify-content-between align-items-center p-2 ">
                <span>
                  {translate.my_tasks} 
                  <Link to="/my-tasks" className="ms-2"><small>{translate.show}</small></Link>
                </span>
                <span className="badge bg-info rounded-pill">{profileInfo.tasks.my}</span>
              </li>
              <li key="p2" className="list-group-item d-flex justify-content-between align-items-center p-2">
                <span>
                  {translate.completed_tasks} 
                  <Link to="/completed-tasks#completed" className="ms-2"><small>{translate.show}</small></Link>
                </span>
                <span className="badge bg-info rounded-pill">{profileInfo.tasks.completed}</span>
              </li>
              <li key="p3" className="list-group-item d-flex justify-content-between align-items-center p-2">
                <span>
                  {translate.awaiting_check_task} 
                  <Link to="/completed-tasks" className="ms-2"><small>{translate.show}</small></Link>
                </span>
                <span className="badge bg-info rounded-pill">{profileInfo.tasks.active}</span>
              </li>
              <li key="p4" className="list-group-item d-flex justify-content-between align-items-center p-2">
                <span>
                  {translate.declined_tasks} 
                  <Link to="/completed-tasks#declined" className="ms-2"><small>{translate.show}</small></Link>
                </span>
                <span className="badge bg-info rounded-pill">{profileInfo.tasks.declined}</span>
              </li>
            </ul>
            <Link to="/edit-profile" className="btn btn-primary w-100 rounded-pill">{translate.edit_profile}</Link>
          </div>
        </div>
      </div>
    </>
  );
};

export default Profile;