INSERT INTO apikamgusv2.services (id, tipo_transporte, tipo_servicio, estado, tipo_pago, pago)
VALUES
    (42, 'MOTO', 'SIMPLE', 'PENDIENTE', 'Yappy', 'PENDIENTE'),
    (43, 'SEDAN', 'MUDANZA', 'TERMINADO', 'Card', 'PAGADO');

INSERT INTO apikamgusv2.driver_services (id, service_id, driver_id, status, confirmed, ispaid)
VALUES
    (100, 42, 200, 'Pendiente', 'NO', 'Pendiente'),
    (101, 43, 200, 'Terminado', 'SI', 'Pagado');

INSERT INTO apikamgusv2.transactions (id, type, status, gateway)
VALUES
    (50, 'visa', 'succeeded', 'stripe');

INSERT INTO proyecto_legacy.servicios (idservicios, tipo_translado, estado, tipo_pago, ispagado)
VALUES
    (77, 'Simple', 'Pendiente', 'Yappy', 'PENDIENTE');

INSERT INTO proyecto_legacy.conductor_servicios (idconductor_servicios, estado, role, ispagado)
VALUES
    (88, 'En Curso', 'CONDUCTOR', 'Pendiente');
