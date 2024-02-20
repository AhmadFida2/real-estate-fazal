from fpdf import FPDF
from datetime import datetime

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
    pdf.ln(5)
    pdf.set_font('Arial', '', 10)
    pdf.cell(0, 5, f'Invoice Number: INV-000{assignment.id}', 0, 0, 'L')
    pdf.cell(0, 5, f'Date: {datetime.now().strftime("%d %b %Y")}', 0, 1, 'R')
    pdf.ln(10)

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
    pdf.set_font('Arial', 'B', 10)
    pdf.cell(0, 5, 'Bill To:', 0, 1, 'L')
    pdf.set_font('Arial', '', 10)
    pdf.cell(0, 5, assignment.client, 0, 1, 'L')
    pdf.cell(0, 5, assignment.property_name, 0, 1, 'L')
    pdf.cell(0, 5, f'{assignment.city}, {assignment.state}, {assignment.zip}', 0, 1, 'L')
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
    pdf.ln(5)

    # Table
    pdf.set_font('Arial', 'B', 10)
    pdf.cell(30, 5, 'Loan #', 1, 0, 'C')
    pdf.cell(30, 5, 'Investor #', 1, 0, 'C')
    pdf.cell(50, 5, 'Property Address', 1, 0, 'C')
    pdf.cell(30, 5, 'City', 1, 0, 'C')
    pdf.cell(20, 5, 'State', 1, 0, 'C')
    pdf.cell(30, 5, 'Amount', 1, 1, 'C')

    pdf.set_font('Arial', '', 10)
    pdf.cell(30, 5, assignment.loan_number, 1, 0, 'C')
    pdf.cell(30, 5, assignment.investor_number, 1, 0, 'C')
    pdf.cell(50, 5, assignment.property_address, 1, 0, 'C')
    pdf.cell(30, 5, assignment.city, 1, 0, 'C')
    pdf.cell(20, 5, assignment.state, 1, 0, 'C')
    pdf.cell(30, 5, f"${assignment.payment_info['invoice_amount']}", 1, 1, 'C')

    # Total
    pdf.cell(140, 5, 'Total', 1, 0, 'R')
    pdf.cell(50, 5, f"${assignment.payment_info['invoice_amount']}", 1, 1, 'C')

    # Payments Info
    if assignment.payments():
        pdf.ln(5)
        pdf.set_font('Arial', 'B', 14)
        pdf.cell(0, 15, 'Payments Info', 0, 1, 'C')
        pdf.set_font('Arial', 'B', 10)
        pdf.set_x(30.0)
        pdf.cell(80, 5, 'Date', 1, 0, 'C')
        pdf.cell(80, 5, 'Amount', 1, 1, 'C')

        pdf.set_font('Arial', '', 10)
        for payment in assignment.payments():
            pdf.set_x(30.0)
            pdf.cell(80, 5, datetime.strptime(payment['date'], '%Y-%m-%d').strftime('%d %b %Y'), 1, 0, 'C')
            pdf.cell(80, 5, f"${payment['amount']}", 1, 1, 'C')

    return pdf


class Assignment:
    def __init__(self, id, client, property_name, city, state, zip, loan_number, investor_number, property_address,
                 payment_info):
        self.id = id
        self.client = client
        self.property_name = property_name
        self.city = city
        self.state = state
        self.zip = zip
        self.loan_number = loan_number
        self.investor_number = investor_number
        self.property_address = property_address
        self.payment_info = payment_info

    def payments(self):
        return [{'date': '2023-01-01', 'amount': 1000}, {'date': '2023-02-01', 'amount': 1500}]


# Sample assignment data
assignment_data = {
    'id': '123',
    'client': 'John Doe',
    'property_name': 'Doe Mansion',
    'city': 'New York',
    'state': 'NY',
    'zip': '10001',
    'loan_number': 'LN123',
    'investor_number': 'INV456',
    'property_address': '123 Main St',
    'payment_info': {'invoice_amount': 2500}
}

# Create assignment object
assignment = Assignment(**assignment_data)

# Generate invoice PDF
pdf = generate_invoice(assignment)

# Output PDF to a file
pdf.output('invoices/sample_invoice.pdf')
