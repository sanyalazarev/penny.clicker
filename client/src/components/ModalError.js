import React, { useEffect, useContext } from 'react';
import { ModalContext } from '../ModalContext';

const ModalError = () => {
  const { error, clearError } = useContext(ModalContext);

  useEffect(() => {
    if (error) {
      document.querySelectorAll('.modal.show .btn-close').forEach(button => {
        button.click();
      });

      const modal = new window.bootstrap.Modal(document.getElementById('modalError'));
      modal.show();
    }

    document.getElementById('modalError').addEventListener('hidden.bs.modal', function () {
      clearError()
    });
  }, [error]); // If the error changes, the window will be opened

  return (
    <div className="modal fade mt-3" id="modalError" tabIndex="-1" aria-labelledby="modalErrorLabel" aria-hidden="true">
      <div className="modal-dialog">
        <div className="modal-content">
          <div className="alert alert-dismissible alert-danger mb-0">
            <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <b>Error</b><br />
            {error}
          </div>
        </div>
      </div>
    </div>
  );
};

export default ModalError;
