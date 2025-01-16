import React, { useState, useEffect, useContext } from 'react';
import { useParams, Link } from 'react-router-dom';

import { apiUrl } from '../config';

import { useLanguage } from '../LanguageContext';
import { ModalContext } from '../ModalContext';

import { formattedDate } from '../utils';

import Header from './Header';

const CheckTask = () => {
  const { translate } = useLanguage();

  const { showError } = useContext(ModalContext);

  const { id: task_id } = useParams();

  const [task, setTask] = useState({title: ''});
  const [requests, setRequests] = useState({title: '', awaiting: [], accepted:[], declined: []});

  const fetchRequests = async () => {
    try {
      const response = await fetch(apiUrl + '/tasks/getRequests', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ token: window.token, task_id }),
      });

      if (response.ok) {
        const data = await response.json();

        if(data.success) {
          setTask(data.task);
          setRequests(data.requests);
        }
        else
          showError(data.error);
      } else {
        console.error(response.statusText);
      }
    } catch (error) {
      console.error('Error loading data:', error);
    }
  };


  useEffect(() => {
    fetchRequests();
   }, []);

  const checkRequest = async (request_id, action) => {
    try {
      const response = await fetch(apiUrl + "/tasks/checkRequest", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ token: window.token, request_id, action })
      });
 
      if (response.ok) {
        const data = await response.json();

        if(data.success) {
          fetchRequests();
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

  const acceptRequest = (request_id) => { checkRequest(request_id, 'accept'); }
  const declineRequest = (request_id) => { checkRequest(request_id, 'decline'); }

  return (
  <>
    <Header />

    <div className="container mt-4">
      <h2 className="text-center mb-1">{task.title}</h2>
      <h5 className="text-center mb-2">{translate.check_list}</h5>

      <ul className="nav nav-pills row mb-3" role="tablist">
        <li key="co1" className="nav-item col-6 text-center" role="presentation">
          <a className="nav-link rounded-pill px-2 active" data-bs-toggle="tab" href="#active" aria-selected="true" role="tab">{translate.awaiting} {requests.awaiting.length > 0 ? '(' + requests.awaiting.length + ')' : ''}</a>
        </li>
        <li key="co2" className="nav-item col-6 text-center" role="presentation">
          <a className="nav-link rounded-pill px-2" data-bs-toggle="tab" href="#accepted" aria-selected="false" tabIndex="-1" role="tab">{translate.accepted} {requests.accepted.length > 0 ? '(' + requests.accepted.length + ')' : ''}</a>
        </li>
        <li key="co3" className="nav-item col-6 offset-3 text-center" role="presentation">
          <a className="nav-link rounded-pill px-2" data-bs-toggle="tab" href="#declined" aria-selected="false" tabIndex="-1" role="tab">{translate.declined} {requests.declined.length > 0 ? '(' + requests.declined.length + ')' : ''}</a>
        </li>
      </ul>

      <div className="tab-content">
        <div className="tab-pane fade active show" id="active" role="tabpanel">
          {requests.awaiting.length === 0 && (
          <div className="alert alert-dismissible alert-light">
            {translate.request_for_verification}
          </div>
          )}
          {requests.awaiting.map(request => (
          <div key={request.id} className="card mb-3">
            <div className="card-body container">
              <div className="row mb-2">
                <div className="col-7 text-start">
                  <h5 className="mb-0">@{request.nickname || 'Unknown'}</h5>
                  <small><i>{formattedDate(request.date)}</i></small>
                </div>
                <div className="col-5 text-end">
                  <span className="badge rounded-pill bg-dark">#Telegram ID:<br></br> {request.chat_id}</span>
                </div>
              </div>
              <div className="btn-group w-100" role="group">
                <button className="btn btn-outline-success btn-sm" onClick={() => acceptRequest(request.id)}>{translate.accept}</button>
                <button className="btn btn-outline-danger btn-sm" onClick={() => declineRequest(request.id)}>{translate.decline}</button>
              </div>
            </div>
          </div>
          ))}
        </div>

        <div className="tab-pane fade" id="accepted" role="tabpanel">
          {requests.accepted.length === 0 && (
          <div className="alert alert-dismissible alert-light">
            {translate.reward_list}
          </div>
          )}
          {requests.accepted.map(request => (
          <div key={request.id} className="card mb-3">
            <div className="card-body container">
              <div className="row">
                <div className="col-7 text-start">
                  <h5 className="mb-0">@{request.nickname || 'Unknown'}</h5>
                  <small><i>{formattedDate(request.date)}</i></small>
                </div>
                <div className="col-5 text-end">
                  <span className="badge rounded-pill bg-dark">#Telegram ID:<br></br> {request.chat_id}</span>
                </div>
              </div>
            </div>
          </div>
          ))}
        </div>

        <div className="tab-pane fade" id="declined" role="tabpanel">
          {requests.declined.length === 0 && (
          <div className="alert alert-dismissible alert-light">
              {translate.failed_task_list}
          </div>
          )}

          {requests.declined.map(request => (
          <div key={request.id} className="card mb-3">
            <div className="card-body container">
              <div className="row mb-2">
                <div className="col-7 text-start">
                  <h5 className="mb-0">@{request.nickname || 'Unknown'}</h5>
                  <small><i>{formattedDate(request.date)}</i></small>
                </div>
                <div className="col-5 text-end">
                  <span className="badge rounded-pill bg-dark">#Telegram ID:<br></br> {request.chat_id}</span>
                </div>
              </div>
              <button className="btn btn-outline-success btn-sm w-100" onClick={() => acceptRequest(request.id)}>{translate.accept}</button>
            </div>
          </div>
          ))}
        </div>
      </div>

      <Link to="/my-tasks" className="btn btn-light w-100 rounded-pill">&larr; {translate.back_to_tasks}</Link>
    </div>
  </>
  );
};

export default CheckTask;