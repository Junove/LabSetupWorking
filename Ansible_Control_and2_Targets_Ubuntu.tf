provider "aws" {
  region = "us-west-1"
}

data "aws_security_group" "allin" {
  name = "Everybody All In"
}

variable "number_of_students" {
  description = "Number of students"
  default     = 1
}

variable "base_ami" {
  description = "AMI to start from"
  default     = "ami-0603cb4546aa25a8b"
}

variable "key_name" {
  description = "ssh key to connect"
  default     = "FullStack"
}

# Create the control node instances
resource "aws_instance" "control_node" {
  count = var.number_of_students

  ami                    = var.base_ami
  instance_type          = "t2.micro"
  key_name               = "FullStack"
  vpc_security_group_ids = [data.aws_security_group.allin.id]

  tags = {
    Name = "Student-${count.index + 1}-ControlNode"
  }

  connection {
    type        = "ssh"
    user        = "ubuntu"
    private_key = file("FullStack.pem")
    host        = self.public_ip
  }

  provisioner "remote-exec" {
    inline = [
      "sudo apt update",
      "sudo apt install -y ansible"
    ]
  }
}

# Create the target node instances
resource "aws_instance" "target_node_1" {
  count = var.number_of_students

  ami                    = var.base_ami
  instance_type          = "t2.micro"
  key_name               = "FullStack"
  vpc_security_group_ids = [data.aws_security_group.allin.id]

  tags = {
    Name = "Student-${count.index + 1}-TargetNode-1"
  }
}

resource "aws_instance" "target_node_2" {
  count = var.number_of_students

  ami                    = var.base_ami
  instance_type          = "t2.micro"
  vpc_security_group_ids = [data.aws_security_group.allin.id]

  tags = {
    Name = "Student-${count.index + 1}-TargetNode-2"
  }
}

# Generate inventory files for each student
resource "local_file" "generate_inventory" {
  count = var.number_of_students

  depends_on = [
    aws_instance.target_node_1,
    aws_instance.target_node_2
  ]

  filename = "inventory_${count.index + 1}" # Name each file uniquely
  content  = <<-EOT
[control]
controlnode ansible_host=${aws_instance.control_node[count.index].public_ip}
[webservers]
targetnode1 ansible_host=${aws_instance.target_node_1[count.index].public_ip}
targetnode2 ansible_host=${aws_instance.target_node_2[count.index].public_ip}
EOT
}

# Copy inventory files to control nodes
resource "null_resource" "copy_inventory_files" {
  count = var.number_of_students

  triggers = {
    inventory_file_created = local_file.generate_inventory[count.index].filename
  }

  provisioner "file" {
    source      = local_file.generate_inventory[count.index].filename
    destination = "inventory"
    connection {
      type        = "ssh"
      user        = "ubuntu"
      private_key = file("FullStack.pem")
      host        = aws_instance.control_node[count.index].public_ip
    }
  }
}

# Use remote-exec to run Ansible playbook on control nodes
resource "null_resource" "run_ansible" {
  count = var.number_of_students

  triggers = {
    inventory_copied = local_file.generate_inventory[count.index].id
  }

  provisioner "remote-exec" {
    inline = [
      "git clone https://github.com/ProDataMan/Ansible-Intro.git",
      "ANSIBLE_HOST_KEY_CHECKING=False ansible -m ping all -i ~/inventory",
      "ansible-playbook Ansible-Intro/webservers.yml -i ~/inventory",
      "ansible-playbook Ansible-Intro/InstallJenkins.yml -i ~/inventory"
    ]
  }

  connection {
    type        = "ssh"
    user        = "ubuntu"
    private_key = file("FullStack.pem")
    host        = aws_instance.control_node[count.index].public_ip
  }
}

# Ensure the target directory exists on the target nodes
resource "null_resource" "ensure_html_directory" {
  count = var.number_of_students * 2

  connection {
    type        = "ssh"
    user        = "ubuntu"
    private_key = file("FullStack.pem")
    host        = element(concat(aws_instance.target_node_1.*.public_ip, aws_instance.target_node_2.*.public_ip), count.index)
  }

  provisioner "remote-exec" {
    inline = [
      "sudo mkdir -p /var/www/html"
    ]
  }

  depends_on = [null_resource.run_ansible]
}

# Copy HTML files to target nodes after ensuring the directory exists
resource "null_resource" "copy_html_to_target_nodes" {
  count = var.number_of_students * 2

  depends_on = [null_resource.ensure_html_directory]

  triggers = {
    html_folder_updated = timestamp()
  }

  provisioner "local-exec" {
    command = "scp -o StrictHostKeyChecking=no -o ConnectionAttempts=5 -r -i FullStack.pem ./html ubuntu@${element(concat(aws_instance.target_node_1.*.public_ip, aws_instance.target_node_2.*.public_ip), count.index)}:/var/www/html/"
  }
}
