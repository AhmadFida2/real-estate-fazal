import json
import sys
import requests
from fpdf import FPDF
from datetime import datetime

id = sys.argv[1]


class PDF(FPDF):
    def header(self):
        self.set_font('Arial', 'B', 20)
        self.cell(0, 10, 'Invoice', 0, 1, 'R')
        self.ln(5)

    def footer(self):
        self.set_y(-15)
        self.set_font('Arial', 'I', 8)
        self.cell(0, 10, f'Page {self.page_no()}', 0, 0, 'C')


def generate_invoice(assignment):
    pdf = PDF()
    pdf.add_page()

    # Company Logo
    pdf.image('company_logo.png', x=10, y=10, w=40)

    # Invoice Info
    pdf.ln(10)
    pdf.set_font('Arial', '', 10)
    pdf.cell(0, 5, f'Invoice Number: INV-000{id}', 0, 0, 'L')
    pdf.cell(0, 5, f'Date: {datetime.now().strftime("%d %b %Y")}', 0, 1, 'R')
    pdf.ln(5)

    # Company Address
    pdf.cell(0, 5, 'AMS Real Estate Services, Inc.', 0, 1, 'L')
    pdf.cell(0, 5, '310 Comal St. Bld. A, Ste. 301', 0, 1, 'L')
    pdf.cell(0, 5, 'Austin, TX 78702', 0, 1, 'L')
    pdf.cell(0, 5, 'USA', 0, 1, 'L')
    pdf.ln(5)

    # Terms
    pdf.cell(0, 5, 'Terms: Upon Receipt', 0, 1, 'R')
    pdf.ln(5)

    # Bill To
    pdf.ln(5)
    pdf.set_font('Arial', 'B', 10)
    pdf.cell(0, 5, 'Bill To:', 0, 1, 'L')
    pdf.set_font('Arial', '', 10)
    pdf.cell(0, 5, assignment["client"], 0, 1, 'L')
    pdf.cell(0, 5, assignment["property_name"], 0, 1, 'L')
    pdf.cell(0, 5, f'{assignment["city"]}, {assignment["state"]}, {assignment["zip"]}', 0, 1, 'L')
    pdf.cell(0, 5, 'USA', 0, 1, 'L')
    pdf.ln(5)

    # Remittance Information
    pdf.set_font('Arial', 'B', 10)
    pdf.cell(0, 5, 'Remittance Information:', 0, 1, 'L')
    pdf.set_font('Arial', '', 10)
    pdf.cell(0, 5, 'Bank: Wells Fargo Bank, NA', 0, 1, 'L')
    pdf.cell(0, 5, 'Account Name: AMS Real Estate Services, Inc.', 0, 1, 'L')
    pdf.cell(0, 5, 'Account #: 8045689166', 0, 1, 'L')
    pdf.cell(0, 5, 'Routing # for Wires: 121000248', 0, 1, 'L')
    pdf.cell(0, 5, 'Routing # for ACH: 111900659', 0, 1, 'L')
    pdf.ln(20)

    TABLE_DATA = (
        ("Loan #", "Investor #", "Property Address", "City", "State", "Amount"),
        (assignment["loan_number"], assignment["investor_number"], assignment["property_address"], assignment["city"],
         assignment["state"], f'${assignment["invoice_info"]["invoice_amount"]}'),
    )

    with pdf.table(text_align="CENTER") as table:
        for data_row in TABLE_DATA:
            row = table.row()
            for datum in data_row:
                row.cell(datum)
        row = table.row()
        row.cell('Total', colspan=5)
        row.cell(f"${assignment['invoice_info']['invoice_amount']}")

    # Payments Info
    if assignment["payments"]:
        pdf.ln(5)
        pdf.set_font('Arial', 'B', 14)
        pdf.cell(0, 15, 'Payments Info', 0, 1, 'C')
        pdf.set_font('Arial', 'B', 10)
        pdf.set_x(30.0)
        pdf.cell(80, 5, 'Date', 1, 0, 'C')
        pdf.cell(80, 5, 'Amount', 1, 1, 'C')

        pdf.set_font('Arial', '', 10)
        for payment in assignment["payments"]:
            pdf.set_x(30.0)
            pdf.cell(80, 5, datetime.strptime(payment['date'], '%Y-%m-%d').strftime('%d %b %Y'), 1, 0, 'C')
            pdf.cell(80, 5, f"${payment['amount']}", 1, 1, 'C')

    return pdf


data = requests.get('https://arfihost.online/api/assignment/'+ id)
data = json.loads(data.text)
print(data)

# Generate invoice PDF
pdf = generate_invoice(data)

# Output PDF to a file
pdf.output('invoice_' + id + '.pdf')
