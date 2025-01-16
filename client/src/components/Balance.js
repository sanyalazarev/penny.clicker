import React, { useState, useEffect, useContext } from 'react';
import { Link } from 'react-router-dom';

import { apiUrl, walletAddress } from '../config';

import { useLanguage } from '../LanguageContext';
import { ModalContext } from '../ModalContext';
import { formattedDate } from '../utils';

import Header from './Header';

const Profile = () => {
  const { translate } = useLanguage();

  const { showError, showInfo } = useContext(ModalContext);

  const [balanceInfo, setBalanceInfo] = useState({
    ton_coin: 0, 
    ton_usd: 0, 
    not_coin: 0, 
    not_usd: 0, 
    dogs_coin: 0, 
    dogs_usd: 0, 
    hmstr_coin: 0, 
    hmstr_usd: 0, 
    x_coin: 0, 
    x_usd: 0, 
    total_balance: 0
  });

  const [minWithdrawal, setMinWithdrawal] = useState(0);
  const [rates, setRates] = useState({});

  const [transactions, setTransactions] = useState([]);

  const [refillInfo, setRefillInfo] = useState({currency: "TON", amount: 0, address: ''});

  const fetchBalanceInfo = async () => {
    try {
      const response = await fetch(apiUrl + '/user/getBalanceInfo', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ token: window.token }),
      });

      if (response.ok) {
        const data = await response.json();

        if(data.success) {
          data.balance.ton_usd = Math.round(data.balance.ton_coin * data.rates.ton * 100) / 100;
          data.balance.not_usd = Math.round(data.balance.not_coin * data.rates.not * 100) / 100;
          data.balance.dogs_usd = Math.round(data.balance.dogs_coin * data.rates.dogs * 100) / 100;
          data.balance.hmstr_usd = Math.round(data.balance.hmstr_coin * data.rates.hmstr * 100) / 100;
          data.balance.x_usd = Math.round(data.balance.x_coin * data.rates.x * 100) / 100;

          data.balance.total_balance = Math.round(
            (data.balance.ton_usd + data.balance.not_usd + data.balance.dogs_usd + data.balance.hmstr_usd + data.balance.x_usd) * 100
          ) / 100;

          setMinWithdrawal(data.min_withdrawal);
          setRates(data.rates);
          setBalanceInfo(data.balance);
          setTransactions(data.transactions);
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

  const handleSubmitRefill = async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);

    setRefillInfo({currency: formData.get('currency'), amount: formData.get('amount'), address: formData.get('address')});

    document.querySelectorAll('.modal.show .btn-close').forEach(button => {
      button.click();
    });

    const modal = new window.bootstrap.Modal(document.getElementById('modalRefillStep2'));
    modal.show();
  };

  const handleSubmitRefillStep2= async (e) => {
    e.preventDefault();

    try {
      const response = await fetch(apiUrl + '/balance/addRefillRequest', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({token: window.token, ...refillInfo}),
       });
 
       if (response.ok) {
        const data = await response.json();
 
        if(data.success) {
          showInfo(translate.refill_accepted, translate.refill_request_sent);
        }
        else {
          showError(data.error);
        }
       } else {
        console.error(response.statusText);
       }
    } catch (error) {
      console.error('Error loading data:', error);
    }
  }

  const handleCopyAddress = () => {
    navigator.clipboard.writeText(walletAddress)
      .then(() => {
        alert(translate.wallet_copied);
      })
      .catch((err) => {
        console.error("Failed to copy address: ", err);

        alert(translate.wallet_copy_failed);
      });
  };

  const handleSubmitWithdrawal = async (e) => {
    e.preventDefault();

    try {
      const formData = new FormData(e.target);

      const currency = formData.get('currency');
      const amount = formData.get('amount');

      const minWithdrawalCoins = Math.round((minWithdrawal / rates[currency.toLowerCase()]) * 10000) / 10000;
      if(amount < minWithdrawalCoins) {
        showError('The minimum amount for withdrawal is ' + minWithdrawalCoins + ' ' + currency + ' + network commission.');
        return;
      }

      const response = await fetch(apiUrl + '/balance/addWithdrawalRequest', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          token: window.token, 
          currency: formData.get('currency'), 
          amount: formData.get('amount'), 
          address: formData.get('address')
        })
       });
 
       if (response.ok) {
        const data = await response.json();
 
        if(data.success) {
          showInfo(translate.withdrawal_request_sent, translate.withdrawal_request_accepted);
        }
        else {
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
    fetchBalanceInfo();
  }, []);

  return (
  <>
    <Header />

    <div className="container mt-4">
      <h2 className="text-center mb-3">{translate.balance}</h2>

      <ul className="list-group">
        <li key="b1" className="list-group-item d-flex justify-content-between align-items-center active p-2">
          <b>{translate.total_balance}</b>
          <span className="badge bg-light rounded-pill">&asymp; {balanceInfo.total_balance}$</span>
        </li>
        <li key="b2" className="list-group-item d-flex justify-content-between align-items-center p-2">
          TON
          <span className="badge bg-info rounded-pill">{balanceInfo.ton_coin} &asymp; {balanceInfo.ton_usd}$</span>
        </li>
        <li key="b3" className="list-group-item d-flex justify-content-between align-items-center p-2">
          NOT
          <span className="badge bg-info rounded-pill">{balanceInfo.not_coin} &asymp; {balanceInfo.not_usd}$</span>
        </li>
        <li key="b4" className="list-group-item d-flex justify-content-between align-items-center p-2">
          DOGS
          <span className="badge bg-info rounded-pill">{balanceInfo.dogs_coin} &asymp; {balanceInfo.dogs_usd}$</span>
        </li>
        <li key="b5" className="list-group-item d-flex justify-content-between align-items-center p-2">
          HMSTR
          <span className="badge bg-info rounded-pill">{balanceInfo.hmstr_coin} &asymp; {balanceInfo.hmstr_usd}$</span>
        </li>
        <li key="b6" className="list-group-item d-flex justify-content-between align-items-center p-2">
          X
          <span className="badge bg-info rounded-pill">{balanceInfo.x_coin} &asymp; {balanceInfo.x_usd}$</span>
        </li>
      </ul>

      <div className="row mt-3">
        <div className="col-6">
          <button type="button" className="btn btn-success w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalRefill">&#10515; {translate.deposit}</button>
        </div>
        <div className="col-6">
          <button type="button" className="btn btn-danger w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalWithdrawal">&#10514; {translate.withdrawal}</button>
        </div>
      </div>
    </div>

    <div className="container mt-4">
      <h2 className="text-center mb-3">{translate.transactions_history}</h2>

      {transactions.map(row => (
      <div className="card mb-2">
        <div className="card-body">
          <div className="row">
            <div className="col-12 text-start">
              {row.type} {(row.type_id === 3 || row.type_id === 4 || row.type_id === 5) && (
                <Link to={`/task/${row.object_id}`}>Task #{row.object_id}</Link>
              )}
            </div>
          </div>
          <div className="row">
            <div className="col-6 text-muted">
              <small>{formattedDate(row.date)}</small>
            </div>
            <div className="col-6 text-end">
              <span className={`badge rounded-pill ${row.sum >= 0 ? 'bg-success' : 'bg-danger'}`}>
                {row.sum > 0 ? `+${Math.round(row.sum * 10000) / 10000}` : Math.round(row.sum * 10000) / 10000} {row.currency}
              </span>
            </div>
          </div>
        </div>
      </div>
      ))}
    </div>

    <div className="modal fade" id="modalRefill" tabIndex="-1" aria-labelledby="modalRefillLabel" aria-hidden="true">
    <div className="modal-dialog">
      <div className="modal-content">
        <div className="modal-header">
          <h5 className="modal-title" id="modalRefillLabel">{translate.deposit_form}</h5>
          <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label={translate.close}></button>
        </div>

        <form onSubmit={handleSubmitRefill}>
          <div className="modal-body">
            <div className="row">
              <label htmlFor="currencySelect" className="form-label mt-3">{translate.cryptocurrency}</label>
              <select 
                name="currency" 
                className="form-select" 
                id="currencySelect"
              >
                <option value="TON">TON</option>
                <option value="NOT">NOT</option>
                <option value="DOGS">DOGS</option>
                <option value="HMSTR">HMSTR</option>
                <option value="X">X</option>
              </select>
            </div>
            <div className="row">
              <label htmlFor="amountInput" className="form-label mt-3">{translate.amount}</label>
              <input 
                type="text" 
                name="amount"
                className="form-control" 
                id="amountInput" 
                placeholder="100" 
                required 
              />
            </div>
            <div className="row">
              <label htmlFor="addressInput" className="form-label mt-3">{translate.wallet_address}</label>
              <input 
                type="text" 
                name="address"
                className="form-control" 
                id="addressInput" 
                placeholder="" 
                required 
              />
            </div>
          </div>
          <div className="modal-footer">
            <button type="submit" className="btn btn-primary rounded-pill">{translate.next_step} &rarr;</button>
          </div>
        </form>
      </div>
    </div>
    </div>

    <div className="modal fade" id="modalRefillStep2" tabIndex="-1" aria-labelledby="modalRefillStep2Label" aria-hidden="true">
      <div className="modal-dialog">
        <div className="modal-content">
          <div className="modal-header">
            <h5 className="modal-title" id="modalRefillStep2Label">{translate.make_transaction}</h5>
            <button type="button" className="btn-close" data-bs-toggle="modal" data-bs-target="#modalRefillStep2" aria-label={translate.close}></button>
          </div>

          <form onSubmit={handleSubmitRefillStep2}>
            <div className="modal-body">
              <div className="row">
                <div className="col-8 offset-2">
                  <img src="/wallet-qr.png" className="img-thumbnail mb-3" />
                </div>
              </div>
              <div className="row">
                <div className="col-12">
                  <span
                    dangerouslySetInnerHTML={{
                      __html: translate.transfer_instruction.replace(
                      '{sum}',
                      `<strong>${refillInfo.amount} ${refillInfo.currency}</strong>`
                      ),
                    }}
                  /><br /> 
                  <strong style={{ wordBreak: 'break-all' }}><small>{walletAddress}</small></strong><br /> 
                  <a href="#" onClick={handleCopyAddress} id="copyTooltip">{translate.copy_address}</a>
                </div>
              </div>
            </div>
            <div className="modal-footer">
              <button type="submit" className="btn btn-success rounded-pill" data-bs-dismiss="modal">&#10004; {translate.payment_made}</button>
              <button type="button" className="btn btn-light rounded-pill" data-bs-dismiss="modal">{translate.close}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div className="modal fade" id="modalWithdrawal" tabIndex="-1" aria-labelledby="modalWithdrawalLabel" aria-hidden="true">
      <div className="modal-dialog">
        <div className="modal-content">
          <div className="modal-header">
            <h5 className="modal-title" id="modalWithdrawalLabel">{translate.withdrawal_form}</h5>
            <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label={translate.close}></button>
          </div>
          <form onSubmit={handleSubmitWithdrawal}>
            <div className="modal-body">
              <div className="row">
                <label htmlFor="currencySelect2" className="form-label mt-3">{translate.cryptocurrency}</label>
                <select 
                  name="currency" 
                  className="form-select" 
                  id="currencySelect2"
                  /* onChange={handleChange} */
                >
                  <option value="TON">TON</option>
                  <option value="NOT">NOT</option>
                  <option value="DOGS">DOGS</option>
                  <option value="HMSTR">HMSTR</option>
                  <option value="X">X</option>
                </select>
              </div>
              <div className="row">
                <label htmlFor="amountInput2" className="form-label mt-3">{translate.amount}</label>
                <input 
                  type="text" 
                  name="amount"
                  className="form-control" 
                  id="amountInput2" 
                  placeholder="100" 
                  /* onChange={handleChange} */
                  required 
                />
              </div>
              <div className="row">
                <label htmlFor="addressInput2" className="form-label mt-3">{translate.wallet_address}</label>
                <input 
                  type="text" 
                  name="address"
                  className="form-control" 
                  id="addressInput2" 
                  placeholder="" 
                  /* onChange={handleChange} */
                  required 
                />
              </div>
            </div>
            <div className="modal-footer">
              <button type="submit" className="btn btn-danger rounded-pill">&#10004; {translate.withdrawal}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </>
  );
};

export default Profile;